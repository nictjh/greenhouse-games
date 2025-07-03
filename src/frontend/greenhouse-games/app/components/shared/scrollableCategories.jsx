'use client'
import { HStack, Button, IconButton, Box } from "@chakra-ui/react";
import { useRef, useState, useEffect } from "react";
import { FiChevronLeft, FiChevronRight } from "react-icons/fi";

const ScrollableCategories = () => {
  const scrollRef = useRef(null);
  const [showLeftButton, setShowLeftButton] = useState(false);
  const [showRightButton, setShowRightButton] = useState(true);

  const checkScroll = () => {
    if (scrollRef.current) {
      const { scrollLeft, scrollWidth, clientWidth } = scrollRef.current;
      setShowLeftButton(scrollLeft > 0);
      setShowRightButton(scrollLeft < scrollWidth - clientWidth);
    }
  };

  const scroll = (direction) => {
    if (scrollRef.current) {
      scrollRef.current.scrollBy({
        left: direction === 'right' ? 200 : -200,
        behavior: 'smooth'
      });
    }
  };

  useEffect(() => {
    const currentRef = scrollRef.current;
    currentRef?.addEventListener('scroll', checkScroll);
    return () => currentRef?.removeEventListener('scroll', checkScroll);
  }, []);

  return (
    <Box position="relative">
      {showLeftButton && (
        <IconButton
          icon={<FiChevronLeft />}
          aria-label="Scroll left"
          position="absolute"
          left={2}
          top="50%"
          transform="translateY(-50%)"
          zIndex={1}
          onClick={() => scroll('left')}
          size="sm"
        />
      )}

      <HStack
        ref={scrollRef}
        spacing={4}
        minW="max-content"
        overflowX="auto"
        py={2}
        px={6}
        css={{
          '&::-webkit-scrollbar': { display: 'none' }, // Hide scrollbar
          scrollbarWidth: 'none' // For Firefox
        }}
      >
        {['Math Games', 'Coding Games', 'Language Games', 'Memorization Games', 'Science Games', 'Art Games', 'SEL Games'].map((category) => (
          <Button 
            key={category}
            variant="ghost"
            colorScheme="gray"
            size="sm"
            px={4}
            borderRadius="full"
            _hover={{ bg: 'gray.200' }}
            _active={{ bg: 'gray.300' }}
            color="black"
            flexShrink={0} // Prevent buttons from shrinking
          >
            {category}
          </Button>
        ))}
      </HStack>

      {showRightButton && (
        <IconButton
          icon={<FiChevronRight />}
          aria-label="Scroll right"
          position="absolute"
          right={2}
          top="50%"
          transform="translateY(-50%)"
          zIndex={1}
          onClick={() => scroll('right')}
          size="sm"
        />
      )}
    </Box>
  );
};

export default ScrollableCategories;
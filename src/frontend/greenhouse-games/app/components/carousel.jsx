'use client'

import {
    Box,
    Image,
    Text,
    Flex,
    Avatar,
    Icon,
} from "@chakra-ui/react";
import { FaStar, FaChevronLeft, FaChevronRight } from "react-icons/fa";
import { IconButton } from "@chakra-ui/react";
import { useRef } from "react";


const cardData = [
    {
        title: "Reduce food waste",
        organiser: "Jalan Journey",
        imageUrl: "https://picsum.photos/200/200",
        organiserPic: "",
        rating: 4.6,
        reviews: 78,
        price: "$5.00"
    },
    {
        title: "Reduce food waste",
        organiser: "Jalan Journey",
        imageUrl: "https://picsum.photos/200/200",
        organiserPic: "",
        rating: 4.6,
        reviews: 78,
        price: "$5.00"
    },
    {
        title: "Reduce food waste",
        organiser: "Jalan Journey",
        imageUrl: "https://picsum.photos/200/200",
        organiserPic: "",
        rating: 4.6,
        reviews: 78,
        price: "$5.00"
    },
    {
        title: "Reduce food waste",
        organiser: "Jalan Journey",
        imageUrl: "https://picsum.photos/200/200",
        organiserPic: "",
        rating: 4.6,
        reviews: 78,
        price: "$5.00"
    }
];

const Carousel = () => {
    const scrollRef = useRef(null);

    const scroll = (direction) => {
        if (scrollRef.current) {
            const amount = 220; // width of one card + spacing
            scrollRef.current.scrollBy({
                left: direction === "left" ? -amount : amount,
                behavior: "smooth",
            });
        }
    };

    return (
        <Box overflowX="auto" py={4} bg="#F9FCEA">
            <Flex justify="space-between" align="center" p="4" pt="1">
                <Text fontWeight="bold" color="green.800">Explore popular games</Text>
                {/* idk how to fix the arrows xD  */}
                <Flex gap={2}>
                    <IconButton
                        aria-label="Scroll left"
                        icon={<Icon as={FaChevronLeft} boxSize={4} />}
                        onClick={() => scroll("left")}
                        variant="outline"
                        size="sm"
                        colorScheme="green"
                    />
                    <IconButton
                        aria-label="Scroll right"
                        icon={<Icon as={FaChevronRight} boxSize={4} />}
                        onClick={() => scroll("right")}
                        variant="outline"
                        size="sm"
                        colorScheme="green"
                    />

                </Flex>
            </Flex>

            <Flex gap={6} px={4} width="max-content">
                {cardData.map((item, index) => (
                    <Box
                        key={index}
                        minW="200px"
                        maxW="200px"
                        rounded="xl"
                        overflow="hidden"
                        position="relative"
                        flexShrink={0}
                        display="flex"
                        flexDirection="column"
                        justifyContent="space-between"
                        borderWidth="2px"
                        borderColor="gray.700/20"
                    >
                        <Box
                            textAlign="center" p={4}>
                            <Image
                                src={item.imageUrl}
                                alt={item.title}
                                borderRadius="lg"
                                boxSize="150px"
                                mx="auto"
                                mb={3}
                                objectFit="cover"
                            />
                            <Text fontWeight="bold" fontSize="md" color="green.700">
                                {item.title}
                            </Text>
                            <Flex align="center" gap={2} mt={2}>
                                <Avatar.Root size="xs">
                                    <Avatar.Fallback name={item.organiser} />
                                    <Avatar.Image src={item.organiserPic} />
                                </Avatar.Root>
                                <Text fontSize="xs" fontWeight="medium" color="green.700">
                                    {item.organiser}
                                </Text>
                            </Flex>
                        </Box>

                        <Box px={4} pb={3} mt="auto">
                            <Flex justify="space-between" align="center">
                                <Flex align="center" gap={2}>
                                    <Box>
                                        <Text fontSize="xs" color="black" fontWeight="bold">
                                            {item.price}
                                        </Text>
                                    </Box>
                                </Flex>

                                {/* Rating */}
                                <Flex align="center" gap={1}>
                                    <Text fontSize="12px" fontWeight="bold" textAlign="right" color="black">
                                        {item.rating}
                                    </Text>
                                    <Icon as={FaStar} color="yellow.400" boxSize={4} />
                                    <Text fontSize="xs" color="black">
                                        ({item.reviews})
                                    </Text>
                                </Flex>
                            </Flex>
                        </Box>
                    </Box>
                ))}
            </Flex>
        </Box>
    );
};

export default Carousel;

import {
    Box,
    Button,
    Image,
    Text,
    Flex,
    Avatar,
    Icon,
  } from "@chakra-ui/react";
  import { FaStar } from "react-icons/fa";


const cardData = [
  {
    title: "Reduce food waste",
    organiser: "Jalan Journey",
    imageUrl: "https://picsum.photos/200/200",
    organiserPic: "",
    rating: 4.6,
    reviews: 78
  },
  {
    title: "Reduce food waste",
    organiser: "Jalan Journey",
    imageUrl: "https://picsum.photos/200/200",
    organiserPic: "",
    rating: 4.6,
    reviews: 78
  },
  {
    title: "Reduce food waste",
    organiser: "Jalan Journey",
    imageUrl: "https://picsum.photos/200/200",
    organiserPic: "",
    rating: 4.6,
    reviews: 78
  },
  {
    title: "Reduce food waste",
    organiser: "Jalan Journey",
    imageUrl: "https://picsum.photos/200/200",
    organiserPic: "",
    rating: 4.6,
    reviews: 78
  }
];

const Carousel = () => {
  return (
    <Box overflowX="auto" py={4}>
      <Flex gap={4} px={4} width="max-content">
        {cardData.map((item, index) => (
          <Box
            key={index}
            minW="200px"
            maxW="200px"
            bg="white"
            rounded="xl"
            shadow="md"
            overflow="hidden"
            position="relative"
            flexShrink={0}
            display="flex"
            flexDirection="column"
            justifyContent="space-between"
          >
            <Box textAlign="center" p={4}>
              <Image
                src={item.imageUrl}
                alt={item.title}
                borderRadius="full"
                boxSize="120px"
                mx="auto"
                mb={3}
                objectFit="cover"
              />
              <Text fontWeight="bold" fontSize="lg">
                {item.title}
              </Text>
            </Box>

            <Box px={4} pb={3} mt="auto">
              <Flex justify="space-between" align="center">
                {/* Organiser & Price */}
                <Flex align="center" gap={2}>
                <Avatar.Root size="xs">
                    <Avatar.Fallback name={item.organiser} />
                    <Avatar.Image src={item.organiserPic} />
                </Avatar.Root>
                  <Box>
                    <Text fontSize="xs" fontWeight="medium">
                      {item.organiser}
                    </Text>
                    <Text fontSize="xs" color="gray.500">
                      {item.price}
                    </Text>
                  </Box>
                </Flex>

                {/* Rating */}
                <Flex align="center" gap={1}>
                  <Text fontSize="sm" fontWeight="bold">
                    {item.rating}
                  </Text>
                  <Icon as={FaStar} color="yellow.400" boxSize={4} />
                  <Text fontSize="xs" color="gray.500">
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
